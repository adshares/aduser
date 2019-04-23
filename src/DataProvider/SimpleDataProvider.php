<?php
declare(strict_types = 1);

namespace Adshares\Aduser\DataProvider;

use Adshares\Aduser\External\Browscap;
use Adshares\Aduser\Utils\UrlNormalizer;
use function array_merge;
use function array_unique;
use function array_values;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use function explode;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

final class SimpleDataProvider extends AbstractDataProvider
{
    private const NAME = 'simple';

    /** @var Browscap */
    protected $browscap;

    public function __construct(
        Browscap $browscap,
        RouterInterface $router,
        Connection $connection,
        LoggerInterface $logger
    ) {
        parent::__construct($router, $connection, $logger);
        $this->browscap = $browscap;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function updateData(): bool
    {
        $status = true;

        $status = $this->browscap->update() && $status;

        return $status;
    }

    public function getTaxonomy(): array
    {
        return [
            'user' => [
                [
                    'label' => 'Country',
                    'key' => 'country',
                    'type' => 'dict',
                    'data' => self::sanitazeData(self::getCountries()),
                ],
                [
                    'label' => 'Language',
                    'key' => 'language',
                    'type' => 'dict',
                    'data' => self::sanitazeData(self::getLanguages()),
                ],
            ],
            'site' => [
                [
                    'label' => 'Domain',
                    'key' => 'domain',
                    'type' => 'input',
                ],
                [
                    'label' => 'Tag',
                    'key' => 'tag',
                    'type' => 'input',
                ],
            ],
            'device' => [
                [
                    'label' => 'Device type',
                    'key' => 'type',
                    'type' => 'dict',
                    'data' => self::sanitazeData(self::getDeviceTypes()),
                ],
                [
                    'label' => 'Operating System',
                    'key' => 'os',
                    'type' => 'dict',
                    'data' => self::sanitazeData(self::getOperatingSystems()),
                ],
                [
                    'label' => 'Browser',
                    'key' => 'browser',
                    'type' => 'dict',
                    'data' => self::sanitazeData(self::getBrowsers()),
                ],
            ],
        ];
    }

    public function getKeywords(string $trackingId, Request $request): array
    {
        $log = $this->getRequestLog($trackingId);

        return array_merge(
            $this->getBrowscapKeywords($request),
            $this->getHeadersKeywords($log),
            $this->getSiteKeywords($request)
        );
    }

    public function getHumanScore(string $trackingId, Request $request): float
    {
        $info = $this->getInfo($request->get('device') ?? []);

        if ($info === null) {
            return -1.0;
        }

        return $info->crawler ? 0.0 : -1.0;
    }

    private function getBrowscapKeywords(Request $request): array
    {
        $info = $this->getInfo($request->get('headers') ?? []);

        return $info === null
            ? []
            : [
                'device' => [
                    'type' => self::mapDeviceType($info->device_type),
                    'os' => self::mapOperatingSystem($info->platform),
                    'browser' => self::mapBrowser($info->browser),
                ],
            ];
    }

    private function getInfo(array $headers): ?\stdClass
    {
        $userAgent = $headers['User-Agent'] ?? $headers['user-agent'] ?? '';

        if (empty($userAgent)) {
            return null;
        }

        return $this->browscap->getInfo($userAgent);
    }

    private function getHeadersKeywords(array $log): array
    {
        $keywords = [];

        if (($language = $log['headers']->get('accept-language')) !== null) {
            $langs = [];
            $list = self::getLanguages();
            foreach (explode(',', $language) as $part) {
                $code = strtolower(substr($part, 0, 2));
                if (!array_key_exists($code, $list)) {
                    $code = 'other';
                }
                $langs[] = $code;
            }
            $keywords['user']['language'] = array_unique($langs);
        }
        if (($code = $log['headers']->get('cf-ipcountry')) !== null) {
            $code = strtolower($code);
            if (!array_key_exists($code, self::getCountries())) {
                $code = 'other';
            }
            $keywords['user']['country'] = $code;
        }

        return $keywords;
    }

    private function getSiteKeywords(Request $request): array
    {
        $keywords = [];
        $url = $request->get('url');
        $tags = $request->get('tags');

        if ($url !== null) {
            $url = UrlNormalizer::normalize($url);
            $keywords['site']['domain'] = self::explodeUrl($url);
        }

        if ($tags !== null) {
            $keywords['site']['tag'] = array_map('mb_strtolower', $tags);
        }

        $siteKeywords = $this->fetchAdUserKeywordsForSite($url);

        if ($siteKeywords) {
            if (!isset($keywords['site']['tag'])) {
                $keywords['site']['tag'] = [];
            }

            $keywords['site']['tag'] = array_values(array_unique(array_merge(
                $keywords['site']['tag'],
                $siteKeywords
            )));
        }

        return $keywords;
    }

    private function fetchAdUserKeywordsForSite(string $url): array
    {
        $query = '
    SELECT keywords FROM site s 
    INNER JOIN url_site_map usm ON usm.site_id = s.id 
    WHERE usm.url = :url
    ';

        try {
            $siteKeywords = $this->connection->fetchColumn(
                $query,
                [
                    'url' => $url,
                ],
                0,
                [
                    'string',
                ]
            );

            if ($siteKeywords === false) {
                return [];
            }

            return explode(',', $siteKeywords);
        } catch (DBALException $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    private static function explodeUrl(string $url): array
    {
        if (strpos($url, '//') === false) {
            $url = '//'.$url;
        }

        if (($parts = parse_url($url)) === false) {
            return [];
        }
        $urls = [];

        $cleanedHost = '';
        $cleanedUrl = '';
        if (isset($parts['host'])) {
            $cleanedHost = preg_replace('/^www\./i', '', mb_strtolower($parts['host']));
            $cleanedUrl = '//'.$cleanedHost;
        }
        if (isset($parts['port'])) {
            $cleanedUrl .= ':'.$parts['port'];
        }
        if (!empty($cleanedUrl)) {
            $urls[] = $cleanedUrl;
        }

        $path = '';
        if (isset($parts['path'])) {
            foreach (explode('/', $parts['path']) as $item) {
                if (empty($item)) {
                    continue;
                }
                $path .= '/'.$item;
                $urls[] = $cleanedUrl.$path;
            }
        }

        if (isset($parts['query'])) {
            $urls[] = $cleanedUrl.$path.'?'.$parts['query'];
        }

        if (!empty($cleanedHost)) {
            $host = '';
            foreach (array_reverse(explode('.', $cleanedHost)) as $item) {
                if (empty($item)) {
                    continue;
                }
                if (empty($host)) {
                    $host = $item;
                } else {
                    $host = $item.'.'.$host;
                }
                $urls[] = $host;
            }
        }

        return $urls;
    }

    private static function sanitazeData(array $data): array
    {
        $result = [];
        foreach ($data as $key => $label) {
            $result[] = [
                'key' => $key,
                'label' => $label,
            ];
        }

        return $result;
    }

    private static function getCountries(): array
    {
        return [
            'af' => 'Afghanistan',
            'ax' => 'Åland Islands',
            'al' => 'Albania',
            'dz' => 'Algeria',
            'as' => 'American Samoa',
            'ad' => 'Andorra',
            'ao' => 'Angola',
            'ai' => 'Anguilla',
            'aq' => 'Antarctica',
            'ag' => 'Antigua and Barbuda',
            'ar' => 'Argentina',
            'am' => 'Armenia',
            'aw' => 'Aruba',
            'au' => 'Australia',
            'at' => 'Austria',
            'az' => 'Azerbaijan',
            'bs' => 'Bahamas',
            'bh' => 'Bahrain',
            'bd' => 'Bangladesh',
            'bb' => 'Barbados',
            'by' => 'Belarus',
            'be' => 'Belgium',
            'bz' => 'Belize',
            'bj' => 'Benin',
            'bm' => 'Bermuda',
            'bt' => 'Bhutan',
            'bo' => 'Bolivia, Plurinational State of',
            'bq' => 'Bonaire, Sint Eustatius and Saba',
            'ba' => 'Bosnia and Herzegovina',
            'bw' => 'Botswana',
            'bv' => 'Bouvet Island',
            'br' => 'Brazil',
            'io' => 'British Indian Ocean Territory',
            'bn' => 'Brunei Darussalam',
            'bg' => 'Bulgaria',
            'bf' => 'Burkina Faso',
            'bi' => 'Burundi',
            'kh' => 'Cambodia',
            'cm' => 'Cameroon',
            'ca' => 'Canada',
            'cv' => 'Cape Verde',
            'ky' => 'Cayman Islands',
            'cf' => 'Central African Republic',
            'td' => 'Chad',
            'cl' => 'Chile',
            'cn' => 'China',
            'cx' => 'Christmas Island',
            'cc' => 'Cocos (Keeling) Islands',
            'co' => 'Colombia',
            'km' => 'Comoros',
            'cg' => 'Congo',
            'cd' => 'Congo, the Democratic Republic of the',
            'ck' => 'Cook Islands',
            'cr' => 'Costa Rica',
            'ci' => 'Côte d\'Ivoire',
            'hr' => 'Croatia',
            'cu' => 'Cuba',
            'cw' => 'Curaçao',
            'cy' => 'Cyprus',
            'cz' => 'Czech Republic',
            'dk' => 'Denmark',
            'dj' => 'Djibouti',
            'dm' => 'Dominica',
            'do' => 'Dominican Republic',
            'ec' => 'Ecuador',
            'eg' => 'Egypt',
            'sv' => 'El Salvador',
            'gq' => 'Equatorial Guinea',
            'er' => 'Eritrea',
            'ee' => 'Estonia',
            'et' => 'Ethiopia',
            'fk' => 'Falkland Islands (Malvinas)',
            'fo' => 'Faroe Islands',
            'fj' => 'Fiji',
            'fi' => 'Finland',
            'fr' => 'France',
            'gf' => 'French Guiana',
            'pf' => 'French Polynesia',
            'tf' => 'French Southern Territories',
            'ga' => 'Gabon',
            'gm' => 'Gambia',
            'ge' => 'Georgia',
            'de' => 'Germany',
            'gh' => 'Ghana',
            'gi' => 'Gibraltar',
            'gr' => 'Greece',
            'gl' => 'Greenland',
            'gd' => 'Grenada',
            'gp' => 'Guadeloupe',
            'gu' => 'Guam',
            'gt' => 'Guatemala',
            'gg' => 'Guernsey',
            'gn' => 'Guinea',
            'gw' => 'Guinea-Bissau',
            'gy' => 'Guyana',
            'ht' => 'Haiti',
            'hm' => 'Heard Island and McDonald Islands',
            'va' => 'Holy See (Vatican City State)',
            'hn' => 'Honduras',
            'hk' => 'Hong Kong',
            'hu' => 'Hungary',
            'is' => 'Iceland',
            'in' => 'India',
            'id' => 'Indonesia',
            'ir' => 'Iran, Islamic Republic of',
            'iq' => 'Iraq',
            'ie' => 'Ireland',
            'im' => 'Isle of Man',
            'il' => 'Israel',
            'it' => 'Italy',
            'jm' => 'Jamaica',
            'jp' => 'Japan',
            'je' => 'Jersey',
            'jo' => 'Jordan',
            'kz' => 'Kazakhstan',
            'ke' => 'Kenya',
            'ki' => 'Kiribati',
            'kp' => 'Korea, Democratic People\'s Republic of',
            'kr' => 'Korea, Republic of',
            'kw' => 'Kuwait',
            'kg' => 'Kyrgyzstan',
            'la' => 'Lao People\'s Democratic Republic',
            'lv' => 'Latvia',
            'lb' => 'Lebanon',
            'ls' => 'Lesotho',
            'lr' => 'Liberia',
            'ly' => 'Libya',
            'li' => 'Liechtenstein',
            'lt' => 'Lithuania',
            'lu' => 'Luxembourg',
            'mo' => 'Macao',
            'mk' => 'Macedonia, the Former Yugoslav Republic of',
            'mg' => 'Madagascar',
            'mw' => 'Malawi',
            'my' => 'Malaysia',
            'mv' => 'Maldives',
            'ml' => 'Mali',
            'mt' => 'Malta',
            'mh' => 'Marshall Islands',
            'mq' => 'Martinique',
            'mr' => 'Mauritania',
            'mu' => 'Mauritius',
            'yt' => 'Mayotte',
            'mx' => 'Mexico',
            'fm' => 'Micronesia, Federated States of',
            'md' => 'Moldova, Republic of',
            'mc' => 'Monaco',
            'mn' => 'Mongolia',
            'me' => 'Montenegro',
            'ms' => 'Montserrat',
            'ma' => 'Morocco',
            'mz' => 'Mozambique',
            'mm' => 'Myanmar',
            'na' => 'Namibia',
            'nr' => 'Nauru',
            'np' => 'Nepal',
            'nl' => 'Netherlands',
            'nc' => 'New Caledonia',
            'nz' => 'New Zealand',
            'ni' => 'Nicaragua',
            'ne' => 'Niger',
            'ng' => 'Nigeria',
            'nu' => 'Niue',
            'nf' => 'Norfolk Island',
            'mp' => 'Northern Mariana Islands',
            'no' => 'Norway',
            'om' => 'Oman',
            'pk' => 'Pakistan',
            'pw' => 'Palau',
            'ps' => 'Palestine, State of',
            'pa' => 'Panama',
            'pg' => 'Papua New Guinea',
            'py' => 'Paraguay',
            'pe' => 'Peru',
            'ph' => 'Philippines',
            'pn' => 'Pitcairn',
            'pl' => 'Poland',
            'pt' => 'Portugal',
            'pr' => 'Puerto Rico',
            'qa' => 'Qatar',
            're' => 'Réunion',
            'ro' => 'Romania',
            'ru' => 'Russian Federation',
            'rw' => 'Rwanda',
            'bl' => 'Saint Barthélemy',
            'sh' => 'Saint Helena, Ascension and Tristan da Cunha',
            'kn' => 'Saint Kitts and Nevis',
            'lc' => 'Saint Lucia',
            'mf' => 'Saint Martin (French part)',
            'pm' => 'Saint Pierre and Miquelon',
            'vc' => 'Saint Vincent and the Grenadines',
            'ws' => 'Samoa',
            'sm' => 'San Marino',
            'st' => 'Sao Tome and Principe',
            'sa' => 'Saudi Arabia',
            'sn' => 'Senegal',
            'rs' => 'Serbia',
            'sc' => 'Seychelles',
            'sl' => 'Sierra Leone',
            'sg' => 'Singapore',
            'sx' => 'Sint Maarten (Dutch part)',
            'sk' => 'Slovakia',
            'si' => 'Slovenia',
            'sb' => 'Solomon Islands',
            'so' => 'Somalia',
            'za' => 'South Africa',
            'gs' => 'South Georgia and the South Sandwich Islands',
            'ss' => 'South Sudan',
            'es' => 'Spain',
            'lk' => 'Sri Lanka',
            'sd' => 'Sudan',
            'sr' => 'Surilabel',
            'sj' => 'Svalbard and Jan Mayen',
            'sz' => 'Swaziland',
            'se' => 'Sweden',
            'ch' => 'Switzerland',
            'sy' => 'Syrian Arab Republic',
            'tw' => 'Taiwan, Province of China',
            'tj' => 'Tajikistan',
            'tz' => 'Tanzania, United Republic of',
            'th' => 'Thailand',
            'tl' => 'Timor-Leste',
            'tg' => 'Togo',
            'tk' => 'Tokelau',
            'to' => 'Tonga',
            'tt' => 'Trinidad and Tobago',
            'tn' => 'Tunisia',
            'tr' => 'Turkey',
            'tm' => 'Turkmenistan',
            'tc' => 'Turks and Caicos Islands',
            'tv' => 'Tuvalu',
            'ug' => 'Uganda',
            'ua' => 'Ukraine',
            'ae' => 'United Arab Emirates',
            'gb' => 'United Kingdom',
            'us' => 'United States',
            'um' => 'United States Minor Outlying Islands',
            'uy' => 'Uruguay',
            'uz' => 'Uzbekistan',
            'vu' => 'Vanuatu',
            've' => 'Venezuela, Bolivarian Republic of',
            'vn' => 'Viet Nam',
            'vg' => 'Virgin Islands, British',
            'vi' => 'Virgin Islands, U.S.',
            'wf' => 'Wallis and Futuna',
            'eh' => 'Western Sahara',
            'ye' => 'Yemen',
            'zm' => 'Zambia',
            'zw' => 'Zimbabwe',
            'other' => 'Other',
        ];
    }

    private static function getLanguages(): array
    {
        return [
            'ab' => 'Abkhazian',
            'aa' => 'Afar',
            'af' => 'Afrikaans',
            'ak' => 'Akan',
            'sq' => 'Albanian',
            'am' => 'Amharic',
            'ar' => 'Arabic',
            'an' => 'Aragonese',
            'hy' => 'Armenian',
            'as' => 'Assamese',
            'av' => 'Avaric',
            'ae' => 'Avestan',
            'ay' => 'Aymara',
            'az' => 'Azerbaijani',
            'bm' => 'Bambara',
            'ba' => 'Bashkir',
            'eu' => 'Basque',
            'be' => 'Belarusian',
            'bn' => 'Bengali',
            'bh' => 'Bihari languages',
            'bi' => 'Bislama',
            'bs' => 'Bosnian',
            'br' => 'Breton',
            'bg' => 'Bulgarian',
            'my' => 'Burmese',
            'ca' => 'Catalan',
            'km' => 'Central Khmer',
            'ch' => 'Chamorro',
            'ce' => 'Chechen',
            'ny' => 'Chichewa',
            'zh' => 'Chinese',
            'cu' => 'Church Slavic',
            'cv' => 'Chuvash',
            'kw' => 'Cornish',
            'co' => 'Corsican',
            'cr' => 'Cree',
            'hr' => 'Croatian',
            'cs' => 'Czech',
            'da' => 'Danish',
            'dv' => 'Divehi',
            'nl' => 'Dutch',
            'dz' => 'Dzongkha',
            'en' => 'English',
            'eo' => 'Esperanto',
            'et' => 'Estonian',
            'ee' => 'Ewe',
            'fo' => 'Faroese',
            'fj' => 'Fijian',
            'fi' => 'Finnish',
            'fr' => 'French',
            'ff' => 'Fulah',
            'gd' => 'Gaelic',
            'gl' => 'Galician',
            'lg' => 'Ganda',
            'ka' => 'Georgian',
            'de' => 'German',
            'el' => 'Greek',
            'gn' => 'Guarani',
            'gu' => 'Gujarati',
            'ht' => 'Haitian',
            'ha' => 'Hausa',
            'he' => 'Hebrew',
            'hz' => 'Herero',
            'hi' => 'Hindi',
            'ho' => 'Hiri Motu',
            'hu' => 'Hungarian',
            'is' => 'Icelandic',
            'io' => 'Ido',
            'ig' => 'Igbo',
            'id' => 'Indonesian',
            'ia' => 'Interlingua',
            'ie' => 'Interlingue',
            'iu' => 'Inuktitut',
            'ik' => 'Inupiaq',
            'ga' => 'Irish',
            'it' => 'Italian',
            'ja' => 'Japanese',
            'jv' => 'Javanese',
            'kl' => 'Kalaallisut',
            'kn' => 'Kannada',
            'kr' => 'Kanuri',
            'ks' => 'Kashmiri',
            'kk' => 'Kazakh',
            'ki' => 'Kikuyu',
            'rw' => 'Kinyarwanda',
            'ky' => 'Kirghiz',
            'kv' => 'Komi',
            'kg' => 'Kongo',
            'ko' => 'Korean',
            'kj' => 'Kuanyama',
            'ku' => 'Kurdish',
            'lo' => 'Lao',
            'la' => 'Latin',
            'lv' => 'Latvian',
            'li' => 'Limburgan',
            'ln' => 'Lingala',
            'lt' => 'Lithuanian',
            'lu' => 'Luba-Katanga',
            'lb' => 'Luxembourgish',
            'mk' => 'Macedonian',
            'mg' => 'Malagasy',
            'ms' => 'Malay',
            'ml' => 'Malayalam',
            'mt' => 'Maltese',
            'gv' => 'Manx',
            'mi' => 'Maori',
            'mr' => 'Marathi',
            'mh' => 'Marshallese',
            'mn' => 'Mongolian',
            'na' => 'Nauru',
            'nv' => 'Navajo',
            'ng' => 'Ndonga',
            'ne' => 'Nepali',
            'nd' => 'North Ndebele',
            'se' => 'Northern Sami',
            'no' => 'Norwegian',
            'nb' => 'Norwegian Bokmål',
            'nn' => 'Norwegian Nynorsk',
            'oc' => 'Occitan',
            'oj' => 'Ojibwa',
            'or' => 'Oriya',
            'om' => 'Oromo',
            'os' => 'Ossetian',
            'pi' => 'Pali',
            'pa' => 'Panjabi',
            'ps' => 'Pashto',
            'fa' => 'Persian',
            'pl' => 'Polish',
            'pt' => 'Portuguese',
            'qu' => 'Quechua',
            'ro' => 'Romanian',
            'rm' => 'Romansh',
            'rn' => 'Rundi',
            'ru' => 'Russian',
            'sm' => 'Samoan',
            'sg' => 'Sango',
            'sa' => 'Sanskrit',
            'sc' => 'Sardinian',
            'sr' => 'Serbian',
            'sn' => 'Shona',
            'ii' => 'Sichuan Yi',
            'sd' => 'Sindhi',
            'si' => 'Sinhala',
            'sk' => 'Slovak',
            'sl' => 'Slovenian',
            'so' => 'Somali',
            'nr' => 'South Ndebele',
            'st' => 'Southern Sotho',
            'es' => 'Spanish',
            'su' => 'Sundanese',
            'sw' => 'Swahili',
            'ss' => 'Swati',
            'sv' => 'Swedish',
            'tl' => 'Tagalog',
            'ty' => 'Tahitian',
            'tg' => 'Tajik',
            'ta' => 'Tamil',
            'tt' => 'Tatar',
            'te' => 'Telugu',
            'th' => 'Thai',
            'bo' => 'Tibetan',
            'ti' => 'Tigrinya',
            'to' => 'Tonga',
            'ts' => 'Tsonga',
            'tn' => 'Tswana',
            'tr' => 'Turkish',
            'tk' => 'Turkmen',
            'tw' => 'Twi',
            'ug' => 'Uighur',
            'uk' => 'Ukrainian',
            'ur' => 'Urdu',
            'uz' => 'Uzbek',
            've' => 'Venda',
            'vi' => 'Vietnamese',
            'vo' => 'Volapük',
            'wa' => 'Walloon',
            'cy' => 'Welsh',
            'fy' => 'Western Frisian',
            'wo' => 'Wolof',
            'xh' => 'Xhosa',
            'yi' => 'Yiddish',
            'yo' => 'Yoruba',
            'za' => 'Zhuang',
            'zu' => 'Zulu',
            'other' => 'Other',
        ];
    }

    private static function getDeviceTypes(): array
    {
        return [
            'desktop' => 'Desktop',
            'mobile' => 'Mobile',
            'tablet' => 'Tablet',
            'tv' => 'TV',
            'other' => 'Other',
        ];
    }

    private static function mapDeviceType($device): string
    {
        switch ($device) {
            case 'Desktop':
                return 'desktop';
            case 'Mobile Device':
            case 'Mobile Phone':
                return 'mobile';
            case 'Tablet':
                return 'tablet';
            case 'TV Device':
                return 'tv';
            default:
                return 'other';
        }
    }

    private static function getOperatingSystems(): array
    {
        return [
            'android' => 'Android',
            'apple-os' => 'Apple OS',
            'unix' => 'Unix',
            'windows' => 'Windows',
            'other' => 'Other',
        ];
    }

    private static function mapOperatingSystem($os): string
    {
        switch ($os) {
            case 'Android':
            case 'Android for GoogleTV':
                return 'android';
            case 'iOS':
            case 'macOS':
            case 'MacOSX':
                return 'apple_os';
            case 'AIX':
            case 'Linux':
            case 'BSD':
            case 'Chromecast OS':
            case 'ChromeOS':
            case 'Darwin':
            case 'Debian':
            case 'DragonFly BSD':
            case 'Fedora':
            case 'FirefoxOS':
            case 'FreeBSD':
            case 'HP-UX':
            case 'IRIX64':
            case 'KaiOS':
            case 'Maemo':
            case 'MeeGo':
            case 'NetBSD':
            case 'OpenBSD':
            case 'Red Hat':
            case 'Solaris':
            case 'SunOS':
            case 'Tizen':
            case 'Ubuntu':
            case 'Ubuntu Touch':
            case 'Unix':
                return 'unix';
            case 'Win10':
            case 'Win16':
            case 'Win2000':
            case 'Win31':
            case 'Win32':
            case 'Win64':
            case 'Win7':
            case 'Win8':
            case 'Win8.1':
            case 'Win95':
            case 'Win98':
            case 'WinCE':
            case 'WinME':
            case 'WinMobile':
            case 'WinNT':
            case 'WinPhone':
            case 'WinPhone10':
            case 'WinPhone6':
            case 'WinPhone7':
            case 'WinPhone7.10':
            case 'WinPhone7.5':
            case 'WinPhone7.8':
            case 'WinPhone8':
            case 'WinPhone8.1':
            case 'WinRT8':
            case 'WinRT8.1':
            case 'WinVista':
            case 'WinXP':
                return 'windows';
            default:
                return 'other';
        }
    }

    private static function getBrowsers(): array
    {
        return [
            'chrome' => 'Chrome',
            'edge' => 'Edge',
            'firefox' => 'Firefox',
            'msie' => 'Internet Explorer',
            'opera' => 'Opera',
            'safari' => 'Safari',
            'other' => 'Other',
        ];
    }

    private static function mapBrowser($device): string
    {
        switch ($device) {
            case 'Chrome':
            case 'ChromePlus':
            case 'Chromium':
                return 'chrome';
            case 'Edge':
            case 'Edge Mobile':
                return 'edge';
            case 'Firefox':
            case 'Firefox Focus':
            case 'Firefox for iOS':
            case 'Mozilla':
                return 'firefox';
            case 'IE':
            case 'IEMobile':
                return 'msie';
            case 'Opera':
            case 'Opera Mini':
            case 'Opera Mobile':
            case 'Opera Neon':
            case 'Opera Touch':
                return 'opera';
            case 'Mobile Safari UIWebView':
            case 'Safari':
                return 'safari';
            default:
                return 'other';
        }
    }
}

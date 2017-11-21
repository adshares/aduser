<?php
namespace Aduser\Entity\Helper;

use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * Czas z mikrosekundami
 *
 * 
 */
class BinHexType extends \Doctrine\DBAL\Types\Type
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'binhex';
    }

    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return "VARBINARY({$fieldDeclaration['length']})";
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return ($value !== null)
        ? hex2bin($value) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return $value;
        }

        return strtolower(bin2hex($value));
    }
    

    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
    	return true;
    }
}
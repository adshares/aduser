<?php
namespace Aduser\Entity;

use Doctrine\ORM\Mapping as ORM;
use Adshares\Helper\Utils;

/**
 * Trade
 *
 * @ORM\Table
 * @ORM\Entity
 */
class RequestLog
{
    use EntityTrait;
    
    /**
     * case id - id of impression of banner same for view/click events from same ad display
     * 
     * @var integer
     *
     * @ORM\Column(type="binhex", length=16)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    protected $id;
    
    /**
     * tracking id. Events coming from the same final user should have this in common
     * 
     * @var integer
     *
     * @ORM\Column(type="binhex", length=16)
     */
    protected $uid;
    
    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    protected $headers;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length = 255, nullable = true)
     */
    protected $userAgent;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", length = 1024, nullable = true)
     */
    protected $referer;
    
    /**
     * @var integer
     *
     * @ORM\Column(type="binhex", length=8)
     */
    protected $ip;
    
    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    protected $timestamp;
    
    /**
     * @return string $headers
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param string $headers
     */
    public function setHeaders($headers)
    {
        $this->headers = $headers;
    }

    /**
     * @return number $ip
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param number $ip
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    /**
     * @return number $timestamp
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @param number $timestamp
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
    }

    /**
     * @return number $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param number $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return number $uid
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * @param number $uid
     */
    public function setUid($uid)
    {
        $this->uid = $uid;
    }

    /**
     * @return string $userAgent
     */
    public function getUserAgent()
    {
        return $this->userAgent;
    }

    /**
     * @param string $userAgent
     */
    public function setUserAgent($userAgent)
    {
        $this->userAgent = $userAgent;
    }

    /**
     * @return string $referer
     */
    public function getReferer()
    {
        return $this->referer;
    }

    /**
     * @param string $referer
     */
    public function setReferer($referer)
    {
        $this->referer = $referer;
    }

    
    
  
}
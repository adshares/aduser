<?php
namespace Aduser\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Trade
 *
 * @ORM\Table
 * @ORM\Entity
 */
class User
{
    use EntityTrait;
    
    /**
     * @var string
     *
     * @ORM\Column(type="binhex", length = 16)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    protected $uid;    
  
    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    protected $humanScore = 500;
    
    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     */
    protected $visits = 0;
    /**
     * @return string $uid
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * @param string $uid
     */
    public function setUid($uid)
    {
        $this->uid = $uid;
    }

    /**
     * @return number $humanScore
     */
    public function getHumanScore()
    {
        return $this->humanScore;
    }

    /**
     * @param number $humanScore
     */
    public function setHumanScore($humanScore)
    {
        $this->humanScore = $humanScore;
    }

    /**
     * @return number $visits
     */
    public function getVisits()
    {
        return $this->visits;
    }

    /**
     * @param number $visits
     */
    public function setVisits($visits)
    {
        $this->visits = $visits;
    }

    
    
}
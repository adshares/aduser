<?php

namespace Adshares\Aduser\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class DataController extends AbstractController
{
    public function taxonomy()
    {
        return new Response('taxonomy');
    }

    public function data()
    {
        return new Response('data');
    }
}
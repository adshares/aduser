<?php
declare(strict_types = 1);

namespace Adshares\Aduser\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class DataController extends AbstractController
{
    public function taxonomy(): Response
    {
        return new Response('taxonomy');
    }

    public function data(): Response
    {
        return new Response('data');
    }
}

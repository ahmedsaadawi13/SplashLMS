<?php
// FILE: /app/controllers/HomeController.php

require_once __DIR__ . '/../core/Controller.php';

/**
 * HomeController - Handles public pages
 */
class HomeController extends Controller
{
    /**
     * Show homepage
     */
    public function index()
    {
        $this->view('home/index');
    }

    /**
     * Show about page
     */
    public function about()
    {
        $this->view('home/about');
    }
}

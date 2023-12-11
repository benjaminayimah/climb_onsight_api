<?php

namespace App\Traits;

trait ColorTrait
{
    public function getRandomColor()
    {
        $colors = ['#f45151', '#7854da', '#f2ba21', '#3b76e7', '#565656', '#3b9f33', '#199f99', '#d958bb']; 
        return $colors[array_rand($colors)];
    }
}

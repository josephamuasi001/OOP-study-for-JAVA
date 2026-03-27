<?php

class Car {
    private $speed;

    public function setSpeed($speed) {
        if($speed < 0) {
            echo "Invalid speed";
        } else {
            $this->speed = $speed;
        }
        
    }

    public function getSpeed() {
        return $this->speed;
    }
}


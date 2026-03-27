<?php

class Car {
    public $brand;

    public function drive() {
        echo "Car is moving running";
    }
}

$c1 = new Car();

$c1->brand = "BMW";
$c1->drive();


?>
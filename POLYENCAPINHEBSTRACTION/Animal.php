<?php

class Animal {
    protected $name;

    function makeSound() {
        echo "Animal sound";
    }
}

class Dog extends Animal {

}
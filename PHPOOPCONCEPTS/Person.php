<?php

class Person {
    private $name;

    public function setName($name) {
        $this->name = $name;
    }

    public function getName() {
        return $this->name;
    }
}


$p1 = new Person();
$p1->setName("Joseph");
echo "Name: " . $p1->getName();
<?php

class Person {
    private $name;
    private $age;

    public function __construct($name, $age) {
        $this->name = $name;
        $this->age = $age;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function getName() {
        return $this->name;
    }

    public function setAge($age) {
        if ($age > 0 ) {
            $this->age = $age;
        } else {
            echo "Invalid age <br>";
        }
    }
    public function getAge() {
        return $this->age;
    }

}


require_once "Person.php";
$person1 = new Person("Joseph", 9);

echo "Student name: " .$person1->getName();
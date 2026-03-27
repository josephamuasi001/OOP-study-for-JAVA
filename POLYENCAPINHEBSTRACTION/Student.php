<?php
class Student {
    private $name;
    private $grade;

    public function __construct($name, $grade) {
        
    }

    public function setName($name) {
        if ($name != NULL) {
            $this->name = $name;
        } else {
            echo "Invalid name";
        }
    }

    public function getName() {
        return $this->name;
    }

    public function setGrade($grade) {
        if($grade >= 0 && $grade <= 100) {
            $this->grade = $grade;
        } else {
            echo "Invalid grade";
        }
    }

    public function getGrade(){
        return $this->grade;
    }
}



$student1 = new Student("Josepn", 90);

$student1->getName();
$student1->getGrade();


$student2 = new Student("", 899);
$student2->getName();
$student2->getGrade();
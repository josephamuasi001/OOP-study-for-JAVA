<?php
class Student {
    private $name;
    private $grade;

    public function __construct($name, $grade) {
        $this->setName($name);
        $this->setGrade($grade);
    }

    public function setName($name) {
        if (!empty($name)) {
            $this->name = $name;
        } else {
            echo "Invalid name<br>";
        }
    }

    public function getName() {
        return $this->name;
    }

    public function setGrade($grade) {
        if ($grade >= 0 && $grade <= 100) {
            $this->grade = $grade;
        } else {
            echo "Invalid grade<br>";
        }
    }

    public function getGrade(){
        return $this->grade;
    }
}

$student1 = new Student("Joseph", 90);
echo "Name: " . $student1->getName() . "<br>";
echo "Grade: " . $student1->getGrade() . "<br>";

$student2 = new Student("", 899);
echo "Name: " . $student2->getName() . "<br>";
echo "Grade: " . $student2->getGrade() . "<br>";
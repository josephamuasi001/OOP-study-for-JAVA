package OOPEXERCISES;

abstract class Employee {
    double salary;
    Employee(double salary) {
        this.salary = salary;
    }
    abstract double calculateSalary();    
}

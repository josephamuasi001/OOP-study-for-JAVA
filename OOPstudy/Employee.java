package OOPstudy;

class Employee {
    String name;
    double salary;

    Employee(String name, double salary) {
        this.name = name;
        this.salary = salary;
    }

    double calculateAnnualSalary() {
        return salary * 12;
    } 
}

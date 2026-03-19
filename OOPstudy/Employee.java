package OOPstudy;

public class Employee {
    String name;
    double salary;

    Employee(String name, double salary, double bonus) {
        this.name = name;
        this.salary = salary;
    }

    double calculateAnnualSalary() {
        return salary * 12;
    } 
}

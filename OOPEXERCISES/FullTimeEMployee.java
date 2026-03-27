package OOPEXERCISES;

public class FullTimeEMployee extends Employee{
    double bonus;
    double salary;
    FullTimeEMployee(double salary,double bonus) {
        super(salary);
        this.bonus = bonus;
    }

    @Override
    double calculateSalary() {
        return salary + bonus;
    }
}

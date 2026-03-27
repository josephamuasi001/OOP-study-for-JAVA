package OOPstudy;

class FullTimeEmployee extends Employee{
    double bonus;

    FullTimeEmployee (String name, double salary, double bonus) {
        super(name, salary);
        this.bonus = bonus;
    }
    @Override
    double calculateAnnualSalary() {
        return (salary * 12) + bonus;
    }
}

package OOPstudy;

class PartTimeEmployee extends Employee {
    int hoursWorked;
    int hourlyRate;

    PartTimeEmployee(String name, double salary, int hoursWorked, int hourlyRate) {
        super(name, salary);
        this.hoursWorked = hoursWorked;
        this.hourlyRate = hourlyRate;
    }
    @Override
    double calculateAnnualSalary() {
        return 12 * (hourlyRate * hoursWorked) ;
    }
}

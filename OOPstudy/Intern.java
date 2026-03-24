package OOPstudy;

public class Intern extends Worker {
    int hoursWorked;
    int hourlyRate;

    Intern(String name, double baseSalary, int hoursWorked, int hourlyRate) {
        super(name, baseSalary);
        this.hoursWorked = hoursWorked;
        this.hourlyRate = hourlyRate;
    }

    @Override
    public double calculatePay() {
        return (hourlyRate * hoursWorked);
    } 
}

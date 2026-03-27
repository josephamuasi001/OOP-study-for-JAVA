package OOPEXERCISES;

public class PartTimeEMployee extends Employee{
    double hoursWorked;
    double hourlyRate;

    PartTimeEMployee(double salary, double hoursWorked, double hourlyRate) {
        super(salary);
        this.hoursWorked = hoursWorked;
        this.hourlyRate = hourlyRate;
    }

    @Override
    double calculateSalary(){
        return (hoursWorked * hourlyRate);
    }
}

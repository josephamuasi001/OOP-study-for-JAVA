package OOPstudy;

public class Manager extends Worker{
    double bonus;
    Manager(String name, double baseSalary, double bonus) {
        super(name, baseSalary);
        this.bonus = bonus;
    }
    @Override
    public double calculatePay() {
        return (baseSalary + bonus);
    }
}

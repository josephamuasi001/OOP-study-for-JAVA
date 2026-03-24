package OOPstudy;

public class Main {
    public static void main(String[] args) {
       Manager m1 = new Manager("Joseph ", 8999, 90);
       System.out.println("Pay: $" + m1.calculatePay());
       Intern i1 = new Intern("Joseph", 0, 9, 900);
       System.out.println("Pay: $" + i1.calculatePay());
    }
}

package OOPstudy;

public class MobileMoneyPayment extends Payment{
    @Override
    public void processPayment(double amount) {
        System.out.println("You are paying " + amount + " with momo ");
    }
    
}

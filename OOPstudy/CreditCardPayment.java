package OOPstudy;

public class CreditCardPayment extends Payment {
    @Override
    public void processPayment(double amount) {
        System.out.println("You are using your credit card for payment");
    }
}

package OOPstudy;

public class BankTransferPayment extends Payment{
    @Override
    public void processPayment(double amount) {
        System.out.println("You are paying " + amount + " from your bank account!!");
    }
}

package OOPstudy;

public class Main3 {
    public static void main(String[] args) {
        CreditCardPayment c1 = new CreditCardPayment();
        MobileMoneyPayment m1 = new MobileMoneyPayment();
        BankTransferPayment b1 = new BankTransferPayment();

        Payment[] payments = {c1, m1, b1};

        for(Payment payment : payments) {
            payment.processPayment(800);
        }
    } 

}

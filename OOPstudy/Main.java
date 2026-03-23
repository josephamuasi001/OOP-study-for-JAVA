package OOPstudy;

public class Main {
    public static void main(String[] args) {
        ATMOperations atm = new BankATM(1000);

        atm.deposit(500);
        atm.withdraw(200);
        atm.checkBalance();
    }
}

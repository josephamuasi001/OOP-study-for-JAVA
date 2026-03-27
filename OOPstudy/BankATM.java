package OOPstudy;

public class BankATM implements ATMOperations {

    private double balance; // hidden data

    public BankATM(double initialBalance) {
        this.balance = initialBalance;
    }

    @Override
    public void withdraw(double amount) {
        if (amount > 0 && amount <= balance) {
            balance -= amount;
            System.out.println("Withdrawn: $" + amount);
        } else {
            System.out.println("Invalid withdrawal");
        }
    }

    @Override
    public void deposit(double amount) {
        if (amount > 0) {
            balance += amount;
            System.out.println("Deposited: $" + amount);
        } else {
            System.out.println("Invalid deposit");
        }
    }

    @Override
    public void checkBalance() {
        System.out.println("Current balance: $" + balance);
    }
}
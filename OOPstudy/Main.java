package OOPstudy;

public class Main {
    public static void main(String[] args) {
        SavingsAccount s1 = new SavingsAccount("AC900U672", "Joseph Amuasi", 2000);
        s1.addInterest(6);
        System.out.println("Balance = " + s1.getBalance());
    }
}

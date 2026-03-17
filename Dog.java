public class Dog extends Animal{
    private int numberOfStrings;

    public Dog(String name, int age,int numberOfStrings) {
        super(name, age);
        this.numberOfStrings = numberOfStrings;
    }

    public void displayInfo(){
        super.displayInfo();
        System.out.println("Number of strings: " + this.numberOfStrings);
    }
}

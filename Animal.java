public class Animal {
    private String name;
    private int age;

    public Animal(String name, int age) {
        this.name = name;
        this.age = age;

    }
    public void setName(String name) {
        this.name = name;
    }
    public void setAge(int age) {
        this.age = age;
    }

    public String getName() {
        return name;
    }
    public void displayInfo(){
        System.out.println("My name is: " + this.name + this.age );
    }

    public void eat( ) {
        System.out.println("Eating ");
    }
    
}
package OOPEXERCISES;

public class Vehicle {
    String type;
    Vehicle(String type) {
        this.type = type;
    }
    void start() {
        System.out.println("Your vehicle engine" + type + "has started");
    }
}

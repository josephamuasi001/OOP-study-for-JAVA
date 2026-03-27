package OOPEXERCISES;

public class Motorcycle extends Vehicle{
    String model;
    Motorcycle(String model) {
        this.model = model;
    }

    @Override
    void start() {
        System.out.println("You motorcycle model " + model + " has started");
    }
}

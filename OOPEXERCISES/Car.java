package OOPEXERCISES;

import OOPstudy.vehicle1;

public class Car extends Vehicle{
    String type;
    Car(String type) {
        this.type = type;
    }

    @Override
    void start() {
        System.out.println("Your car of type " + type + " has started");
    }
}

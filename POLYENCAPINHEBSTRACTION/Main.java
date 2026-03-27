package POLYENCAPINHEBSTRACTION;

public class Main {
    public static void main(String[] args) {
        Dog d1 = new Dog();
        Cat c1 = new Cat();

        Animal[] animals = {d1, c1};

        for(Animal animal : animals) {
            animal.makeSound();
        }
    
    }
}

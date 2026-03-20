package OOPstudy;

class Car extends Vehicle {
    Car(String brand) {
        super(brand);
    } 
    @Override
    void startEngine() {
        System.out.println("Your car " + brand + " engine has started !");
    }
}

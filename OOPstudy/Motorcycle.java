package OOPstudy;

class Motorcycle extends Vehicle {
    Motorcycle (String brand) {
        super(brand);
    }    
    @Override
    void startEngine() {
        System.out.println("Your motorcycle " + brand 
        + " engine has started !");
    }
}

package OOPEXERCISES;

public class Main {
    public static void main(String[] args) {
        Circle c1 = new Circle(9);

        System.out.println("Area of Circle: " + c1.calculateArea());
        System.out.println("Radius: " + c1.radius);
        Rectangle r1 = new Rectangle(9, 10);
        System.out.println("Area of Rectangle: " + r1.calculateArea());
        System.out.println("Radius ");
    }
}

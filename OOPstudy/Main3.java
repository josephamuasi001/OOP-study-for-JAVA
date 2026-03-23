package OOPstudy;

public class Main3 {
    public static void main(String[] args) {
       Circle circle = new Circle(7);
       Triangle triangle = new Triangle(6, 15);
       Rectangle rectangle = new Rectangle(10, 8);
       System.out.println("Area: " + circle.area());
       System.out.println("Area: " + rectangle.area());
       System.out.println("Area: " + triangle.area());

    } 

}

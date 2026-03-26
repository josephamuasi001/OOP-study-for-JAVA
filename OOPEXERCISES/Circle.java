package OOPEXERCISES;

public class Circle extends Shape{
    double radius;

    Circle(float radius) {
        this.radius = radius;
    }

    @Override
    double calculateArea() {
        return 22/7 * radius * radius;
    }
}

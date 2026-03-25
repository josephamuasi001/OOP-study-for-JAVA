package OOPEXERCISES;

public class Circle extends Shape{
    float radius;

    Circle(float radius) {
        this.radius = radius;
    }

    @Override
    float calculateArea() {
        return 22/7 * radius * radius;
    }
}

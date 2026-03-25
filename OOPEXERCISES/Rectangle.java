package OOPEXERCISES;

public class Rectangle extends Shape{
    float length;
    float width;
    Rectangle(float length, float width) {
        this.length = length;
        this.width = width;
    }

    @Override
    float calculateArea() {
        return length * width;
    }
}

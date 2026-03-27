package OOPstudy;

public class Triangle extends Shape{
    double height;
    double base;

    Triangle(double height, double base) {
        this.base = base;
        this.height = height;
    }

    @Override
    double area() {
        return 0.5 * base * height;
    };
}

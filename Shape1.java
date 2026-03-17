public abstract class Shape1 {
    protected int length;
    protected int width;
    public Shape1(int length, int width) {
        this.length = length;
        this.width = width;
    }

    public int getArea(){
        return length * width;
    }

    public abstract int getPerimeter();
}

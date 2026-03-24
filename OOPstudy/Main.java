package OOPstudy;

public class Main {
    public static void main(String[] args) {
        Course c1 = new Course("MATH223", "Calculus 2", 98);
        c1.getRemark();
        System.out.println("Course code: " + c1.getCourseCode());
    }
}

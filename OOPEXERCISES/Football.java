package OOPEXERCISES;

public class Football implements Playable{
    String type;
    Football(String type){
        this.type = type;
    }

    @Override
    public void play() {
        System.out.println("The football type " + type + " is playable");
    }
}

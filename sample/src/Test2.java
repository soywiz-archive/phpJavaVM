
public class Test2 {
	public int value;

	public int getValue() {
		return value * getMultiplier();
	}
	
	static protected int getMultiplier() {
		return 1 * 3;
	}

	private static long S11 = 7L;
	private static long S12 = 7L;
}

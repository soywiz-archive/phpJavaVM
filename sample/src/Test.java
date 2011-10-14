
public class Test {
	static public void main(String[] args) {
		test1();
		test2();
		test3();
		test4(777);
		test5(7777777);
		test6("Hello World!");
		test7(new java.lang.Integer(-77));
		test8();
	}
	
	static public void test1() {
		int n = 10;
		int m = 20;
		System.out.println("Hello World! n=" + n + ", m=" + m);
	}
	
	static public void test2() {
		for (int z = 0; z < 10; z++) System.out.println(z);
	}
	
	static public void test3() {
		for (int z = 9; z >= 0; z--) System.out.println(z);
	}
	
	static public void test4(int value) {
		System.out.println("test4:" + value);
	}
	
	static public void test5(int value) {
		System.out.println("test5:" + value);
	}
	
	static public void test6(String value) {
		System.out.println(value);
	}
	
	static public void test7(Integer value) {
		System.out.println(value.byteValue());
		System.out.println(new Integer(-7777777).byteValue());
		System.out.println(new Integer(-7777777).shortValue());
	}
	
	static public void test8() {
		byte[] bytes = new byte[10];
		for (int n = 0; n < bytes.length; n++) bytes[n] = (byte)(n * 10);
		for (int n = 0; n < bytes.length; n++) System.out.println(String.format("test8:%d", bytes[n]));
	}
}

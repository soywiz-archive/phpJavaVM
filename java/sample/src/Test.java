import java.io.UnsupportedEncodingException;
import java.nio.charset.Charset;
import java.security.*;
import java.util.HashMap;


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
		test9();
		test10();
		test11();
		try {
			test12();
		} catch (Exception exception) {
			exception.printStackTrace();
		}
		test13_int_arithmetic();
		test14_int_logic();
		test15_bool_logic();
		test16_branches_0();
		test17_branches_n();
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
	
	static public void test9() {
		HashMap<Integer, Integer> map = new HashMap<Integer, Integer>();
		map.put(0, 30);
		map.put(1, 20);
		map.put(2, 10);
		map.put(3, 0);
		System.out.println(map.get(1) + map.get(2));
	}
	
	static protected void test10() {
		System.out.println(_test10());
	}
	
	static protected int _test10() {
		return 10 * 20;
	}
	
	static public void test11() {
		HashMap<Integer, Integer> map = new HashMap<Integer, Integer>();
		map.put(0, 30);
		map.put(1, 20);
		map.put(2, 10);
		map.put(3, 0);
		
		for (int k : map.keySet()) {
			System.out.println("test11<keys>::" + k);
		}
		
		for (int k : map.values()) {
			System.out.println("test11<values>::" + k);
		}
	}
	
	static public String getHexString(byte[] bytes) {
		StringBuilder hexString = new StringBuilder();
		for (int n = 0; n < bytes.length; n++) {
			String hex = Integer.toHexString(bytes[n] & 0xFF);
			if (hex.length() == 1) {
			    // could use a for loop, but we're only dealing with a single byte
			    hexString.append('0');
			}
			hexString.append(hex);
		}
		return hexString.toString();
	}
	
	static public void test12() throws NoSuchAlgorithmException, UnsupportedEncodingException {
		byte[] bytesOfMessage = "Hello World!".getBytes("UTF-8");

		MessageDigest md = MessageDigest.getInstance("MD5");
		byte[] thedigest = md.digest(bytesOfMessage);
		System.out.println("test12:" + getHexString(thedigest));
	}
	
	static public void test13_int_arithmetic() {
		int a = 1;
		int b = 17;
		int c = 7777777;
		int d = 3;
		int e = (((a + b) * c) / 1000) - d;
		System.out.println(e);
	}
	
	static public void test14_int_logic() {
		int a = 3;
		int b = 4;
		int c = 7;
		int d = 33;
		//int e = 99;
		System.out.println(~(a ^ b | c & d));
	}
	
	static public void test15_bool_logic() {
		boolean a = false;
		boolean b = true;
		boolean c = true;
		
		System.out.println(!((a || b) && c));
	}
	
	static public void test16_branches_0() {
		int l = 0;
		if (l < 0) System.out.print("y"); else System.out.print("n");  
		if (l > 0) System.out.print("y"); else System.out.print("n");
		if (l == 0) System.out.print("y"); else System.out.print("n");
		if (l != 0) System.out.print("y"); else System.out.print("n");
		if (l <= 0) System.out.print("y"); else System.out.print("n");
		if (l >= 0) System.out.print("y"); else System.out.print("n");
		System.out.println("");
	}
	
	static public void test17_branches_n() {
		int l = 0, r = 1;
		if (l < r) System.out.print("y"); else System.out.print("n");
		if (l > r) System.out.print("y"); else System.out.print("n");
		if (l == r) System.out.print("y"); else System.out.print("n");
		if (l != r) System.out.print("y"); else System.out.print("n");
		if (l <= r) System.out.print("y"); else System.out.print("n");
		if (l >= r) System.out.print("y"); else System.out.print("n");
		System.out.println("");
	}
}

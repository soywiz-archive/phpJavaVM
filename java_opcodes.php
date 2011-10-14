<?php

// http://java.sun.com/docs/books/jvms/second_edition/html/Instructions2.doc5.html
class JavaOpcodes {
	const OP_NOP         = 0x00;
	const OP_ACONST_NULL = 0x01;
	const OP_ICONST_M1   = 0x02;
	const OP_ICONST_0    = 0x03;
	const OP_ICONST_1    = 0x04;
	const OP_ICONST_2    = 0x05;
	const OP_ICONST_3    = 0x06;
	const OP_ICONST_4    = 0x07;
	const OP_ICONST_5    = 0x08;
	const OP_LCONST_0    = 0x09;
	const OP_LCONST_1    = 0x0A;
	const OP_FCONST_0    = 0x0B;
	const OP_FCONST_1    = 0x0C;
	const OP_FCONST_2    = 0x0D;
	const OP_DCONST_0    = 0x0E;
	const OP_DCONST_1    = 0x0F;
	const OP_BIPUSH      = 0x10;
	const OP_SIPUSH      = 0x11;
	const OP_LDC         = 0x12;
	const OP_LDC_W       = 0x13;
	const OP_LDC2_W      = 0x14;
	const OP_ILOAD       = 0x15;
	const OP_LLOAD       = 0x16;
	const OP_FLOAD       = 0x17;
	const OP_DLOAD       = 0x18;
	const OP_ALOAD       = 0x19;
	const OP_ILOAD_0     = 0x1A;
	const OP_ILOAD_1     = 0x1B;
	const OP_ILOAD_2     = 0x1C;
	const OP_ILOAD_3     = 0x1D;
	const OP_LLOAD_0     = 0x1E;
	const OP_LLOAD_1     = 0x1F;
	const OP_LLOAD_2     = 0x20;
	const OP_LLOAD_3     = 0x21;
	const OP_FLOAD_0     = 0x22;
	const OP_FLOAD_1     = 0x23;
	const OP_FLOAD_2     = 0x24;
	const OP_FLOAD_3     = 0x25;
	const OP_DLOAD_0     = 0x26;
	const OP_DLOAD_1     = 0x27;
	const OP_DLOAD_2     = 0x28;
	const OP_DLOAD_3     = 0x29;
	const OP_ALOAD_0     = 0x2A;
	const OP_ALOAD_1     = 0x2B;
	const OP_ALOAD_2     = 0x2C;
	const OP_ALOAD_3     = 0x2D;
	const OP_IALOAD      = 0x2E;
	const OP_LALOAD      = 0x2F;
	const OP_FALOAD      = 0x30;
	const OP_DALOAD      = 0x31;
	const OP_AALOAD      = 0x32;
	const OP_BALOAD      = 0x33;
	const OP_CALOAD      = 0x34;
	const OP_SALOAD      = 0x35;
	const OP_ISTORE      = 0x36;
	const OP_LSTORE      = 0x37;
	const OP_FSTORE      = 0x38;
	const OP_DSTORE      = 0x39;
	const OP_ASTORE      = 0x3A;
	const OP_ISTORE_0    = 0x3B;
	const OP_ISTORE_1    = 0x3C;
	const OP_ISTORE_2    = 0x3D;
	const OP_ISTORE_3    = 0x3E;
	const OP_LSTORE_0    = 0x3F;
	const OP_LSTORE_1    = 0x40;
	const OP_LSTORE_2    = 0x41;
	const OP_LSTORE_3    = 0x42;
	const OP_FSTORE_0    = 0x43;
	const OP_FSTORE_1    = 0x44;
	const OP_FSTORE_2    = 0x45;
	const OP_FSTORE_3    = 0x46;
	const OP_DSTORE_0    = 0x47;
	const OP_DSTORE_1    = 0x48;
	const OP_DSTORE_2    = 0x49;
	const OP_DSTORE_3    = 0x4A;
	const OP_ASTORE_0    = 0x4B;
	const OP_ASTORE_1    = 0x4C;
	const OP_ASTORE_2    = 0x4D;
	const OP_ASTORE_3    = 0x4E;
	const OP_IASTORE     = 0x4F;
	const OP_LASTORE     = 0x50;
	const OP_FASTORE     = 0x51;
	const OP_DASTORE     = 0x52;
	const OP_AASTORE     = 0x53;
	const OP_BASTORE     = 0x54;
	const OP_CASTORE     = 0x55;
	const OP_SASTORE     = 0x56;
	const OP_POP         = 0x57;
	const OP_POP2        = 0x58;
	const OP_DUP         = 0x59;
	const OP_DUP_X1      = 0x5A;
	const OP_DUP_X2      = 0x5B;
	const OP_DUP2        = 0x5C;
	const OP_DUP2_X1     = 0x5D;
	const OP_DUP2_X2     = 0x5E;
	const OP_SWAP        = 0x5F;
	const OP_IADD        = 0x60;
	const OP_LADD        = 0x61;
	const OP_FADD        = 0x62;
	const OP_DADD        = 0x63;
	const OP_ISUB        = 0x64;
	const OP_LSUB        = 0x65;
	const OP_FSUB        = 0x66;
	const OP_DSUB        = 0x67;
	const OP_IMUL        = 0x68;
	const OP_LMUL        = 0x69;
	const OP_FMUL        = 0x6A;
	const OP_DMUL        = 0x6B;
	const OP_IDIV        = 0x6C;
	const OP_LDIV        = 0x6D;
	const OP_FDIV        = 0x6E;
	const OP_DDIV        = 0x6F;
	const OP_IREM        = 0x70;
	const OP_LREM        = 0x71;
	const OP_FREM        = 0x72;
	const OP_DREM        = 0x73;
	const OP_INEG        = 0x74;
	const OP_LNEG        = 0x75;
	const OP_FNEG        = 0x76;
	const OP_DNEG        = 0x77;
	const OP_ISHL        = 0x78;
	const OP_LSHL        = 0x79;
	const OP_ISHR        = 0x7A;
	const OP_LSHR        = 0x7B;
	const OP_IUSHR       = 0x7C;
	const OP_LUSHR       = 0x7D;
	const OP_IAND        = 0x7E;
	const OP_LAND        = 0x7F;
	const OP_IOR         = 0x80;
	const OP_LOR = 0x81	 ;
	const OP_IXOR = 0x82	 ;
	const OP_LXOR = 0x83	 ;
	const OP_IINC = 0x84	 ;
	const OP_I2L = 0x85	 ;
	const OP_I2F = 0x86	 ;
	const OP_I2D = 0x87	 ;
	const OP_L2I = 0x88	 ;
	const OP_L2F = 0x89	 ;
	const OP_L2D = 0x8A	 ;
	const OP_F2I = 0x8B	 ;
	const OP_F2L = 0x8C	 ;
	const OP_F2D = 0x8D	 ;
	const OP_D2I = 0x8E	 ;
	const OP_D2L = 0x8F	 ;
	const OP_D2F = 0x90	 ;
	const OP_I2B = 0x91	 ;
	const OP_I2C = 0x92	 ;
	const OP_I2S = 0x93	 ;
	const OP_LCMP = 0x94	 ;
	const OP_FCMPL = 0x95	 ;
	const OP_FCMPG = 0x96	 ;
	const OP_DCMPL = 0x97	 ;
	const OP_DCMPG = 0x98	 ;
	const OP_IFEQ = 0x99	 ;
	const OP_IFNE = 0x9A	 ;
	const OP_IFLT = 0x9B	 ;
	const OP_IFGE = 0x9C	 ;
	const OP_IFGT = 0x9D	 ;
	const OP_IFLE = 0x9E	 ;
	const OP_IF_ICMPEQ = 0x9F	 ;
	const OP_IF_ICMPNE = 0xA0	 ;
	const OP_IF_ICMPLT = 0xA1	 ;
	const OP_IF_ICMPGE = 0xA2	 ;
	const OP_IF_ICMPGT = 0xA3	 ;
	const OP_IF_ICMPLE = 0xA4	 ;
	const OP_IF_ACMPEQ = 0xA5	 ;
	const OP_IF_ACMPNE = 0xA6	 ;
	const OP_GOTO = 0xA7	 ;
	const OP_JSR = 0xA8	 ;
	const OP_RET = 0xA9	 ;
	const OP_TABLESWITCH = 0xAA	 ;
	const OP_LOOKUPSWITCH = 0xAB	 ;
	const OP_IRETURN = 0xAC	 ;
	const OP_LRETURN = 0xAD	 ;
	const OP_FRETURN = 0xAE	 ;
	const OP_DRETURN = 0xAF	 ;
	const OP_ARETURN = 0xB0	 ;
	const OP_RETURN = 0xB1	 ;
	const OP_GETSTATIC = 0xB2	 ;
	const OP_PUTSTATIC = 0xB3	 ;
	const OP_GETFIELD = 0xB4	 ;
	const OP_PUTFIELD = 0xB5	 ;
	const OP_INVOKEVIRTUAL = 0xB6	 ;
	const OP_INVOKESPECIAL = 0xB7	 ;
	const OP_INVOKESTATIC = 0xB8	 ;
	const OP_INVOKEINTERFACE = 0xB9	 ;
	const OP_XXXUNUSEDXXX1 = 0xBA	 ;
	const OP_NEW = 0xBB	 ;
	const OP_NEWARRAY = 0xBC	 ;
	const OP_ANEWARRAY = 0xBD	 ;
	const OP_ARRAYLENGTH = 0xBE	 ;
	const OP_ATHROW = 0xBF	 ;
	const OP_CHECKCAST = 0xC0	 ;
	const OP_INSTANCEOF = 0xC1	 ;
	const OP_MONITORENTER = 0xC2	 ;
	const OP_MONITOREXIT = 0xC3	 ;
	const OP_WIDE = 0xC4	 ;
	const OP_MULTIANEWARRAY = 0xC5	 ;
	const OP_IFNULL = 0xC6	 ;
	const OP_IFNONNULL = 0xC7	 ;
	const OP_GOTO_W = 0xC8	 ;
	const OP_JSR_W = 0xC9	 ;

	// Reserved opcodes:

	const OP_BREAKPOINT = 0xca	 ;
	const OP_IMPDEP1 = 0xfe	 ;
	const OP_IMPDEP2 = 0xff	 ;
	
	static public $OPCODES = array(
		self::OP_ALOAD_0       => array(''),  // Load reference from local variable
		
		self::OP_RETURN        => array(''),  //
		self::OP_IRETURN       => array(''),  //
		self::OP_ARETURN       => array(''),  // 
	
		self::OP_GETSTATIC     => array('2'), // 
		
		self::OP_INVOKESPECIAL   => array('2'),    // Invoke instance method; special handling for superclass, private, and instance initialization method invocations
		self::OP_INVOKEVIRTUAL   => array('2'),    // Invoke instance method; dispatch based on class
		self::OP_INVOKESTATIC    => array('2'),    // Invoke a class (static) method
		self::OP_INVOKEINTERFACE => array('2bb'),  // Invoke interface method
		
		self::OP_LDC           => array('1'),  // Push item from runtime constant pool
		self::OP_BIPUSH        => array('b'),  // Push byte
		self::OP_SIPUSH        => array('w'),  // Push byte
		
		self::OP_ISTORE_0      => array(''),   // Store int into local variable
		self::OP_ISTORE_1      => array(''),   // Store int into local variable
		self::OP_ISTORE_2      => array(''),   // Store int into local variable
		self::OP_ISTORE_3      => array(''),   // Store int into local variable
		
		self::OP_ASTORE_0      => array(''),   // Store reference into local variable
		self::OP_ASTORE_1      => array(''),   // Store reference into local variable
		self::OP_ASTORE_2      => array(''),   // Store reference into local variable
		self::OP_ASTORE_3      => array(''),   // Store reference into local variable
		
		self::OP_NEW           => array('2'),  // Create new object
		self::OP_DUP           => array(''),   // Duplicate the top operand stack value
		
		self::OP_ILOAD_0       => array(''),   // Load int from local variable
		self::OP_ILOAD_1       => array(''),   // Load int from local variable
		self::OP_ILOAD_2       => array(''),   // Load int from local variable
		self::OP_ILOAD_3       => array(''),   // Load int from local variable

		self::OP_ALOAD_0       => array(''),   // Load reference from local variable
		self::OP_ALOAD_1       => array(''),   // Load reference from local variable
		self::OP_ALOAD_2       => array(''),   // Load reference from local variable
		self::OP_ALOAD_3       => array(''),   // Load reference from local variable
	
		self::OP_ICONST_0      => array(''),   // Push int constant
		self::OP_ICONST_1      => array(''),   // Push int constant
		self::OP_ICONST_2      => array(''),   // Push int constant
		self::OP_ICONST_3      => array(''),   // Push int constant
		self::OP_ICONST_4      => array(''),   // Push int constant
		self::OP_ICONST_5      => array(''),   // Push int constant
		self::OP_GOTO          => array('w'),  // Branch always
		
		self::OP_IINC          => array('bb'), // Increment local variable by constant
		self::OP_IADD          => array('bb'), // 
		self::OP_IMUL          => array(''),   // Multiply int
		self::OP_IAND          => array(''),   // 
		
		self::OP_LMUL          => array(''),   // Multiply long
		
		self::OP_I2B           => array(''),   // Convert int to byte
		
		self::OP_POP           => array(''),   // Pop the top operand stack value
		self::OP_CHECKCAST     => array('2'),  // Check whether object is of given type
		
		self::OP_BASTORE       => array(''),   // Store into byte or boolean array
		self::OP_BALOAD        => array(''),   // Load byte or boolean from array
		self::OP_AASTORE       => array(''),   // Store into reference array
		self::OP_ARRAYLENGTH   => array(''),   // Get length of array
		
		self::OP_IF_ICMPLT     => array('w'),  // Branch if int comparison succeeds
		self::OP_IF_ICMPNE     => array('w'),  // Branch if int comparison succeeds
	
		
		self::OP_IFGE          => array('w'),  // Branch if int comparison with zero succeeds
		self::OP_IFNE          => array('w'),  // Branch if int comparison with zero succeeds
		
		self::OP_NEWARRAY      => array('b'),  // Create new array 
		self::OP_ANEWARRAY     => array('2'),  // Create new array of reference
		
		
	);
	
	static public function getOpcodeName($opcodeId) {
		static $mapIdName = array();
		if (empty($mapIdName)) {
			$class = new ReflectionClass('JavaOpcodes');
			foreach ($class->getConstants() as $constantName => $constantValue) {
				$mapIdName[$constantValue] = $constantName;
			}
		}
		return substr($mapIdName[$opcodeId], 3);
	}
}

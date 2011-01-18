#define ZEND_DEBUG 0

#ifdef ZTS
#define USING_ZTS 1
#else
#define USING_ZTS 0
#endif

#define SUCCESS 0
#define FAILURE -1

#define IS_NULL		0
#define IS_LONG		1
#define IS_DOUBLE	2
#define IS_BOOL		3
#define IS_ARRAY	4
#define IS_OBJECT	5
#define IS_STRING	6
#define IS_RESOURCE	7
#define IS_CONSTANT	8
#define IS_CONSTANT_ARRAY	9

#define E_ERROR				(1<<0L)
#define E_WARNING			(1<<1L)
#define E_PARSE				(1<<2L)
#define E_NOTICE			(1<<3L)
#define E_CORE_ERROR		(1<<4L)
#define E_CORE_WARNING		(1<<5L)
#define E_COMPILE_ERROR		(1<<6L)
#define E_COMPILE_WARNING	(1<<7L)
#define E_USER_ERROR		(1<<8L)
#define E_USER_WARNING		(1<<9L)
#define E_USER_NOTICE		(1<<10L)
#define E_STRICT			(1<<11L)
#define E_RECOVERABLE_ERROR	(1<<12L)
#define E_DEPRECATED		(1<<13L)
#define E_USER_DEPRECATED	(1<<14L)

#define E_ALL (E_ERROR | E_WARNING | E_PARSE | E_NOTICE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING | E_USER_ERROR | E_USER_WARNING | E_USER_NOTICE | E_RECOVERABLE_ERROR | E_DEPRECATED | E_USER_DEPRECATED)
#define E_CORE (E_CORE_ERROR | E_CORE_WARNING)

typedef unsigned int zend_uint;
typedef unsigned int uint;
typedef unsigned long long ulong;

typedef unsigned char zend_bool;
typedef unsigned char zend_uchar;
typedef unsigned int zend_uint;
typedef unsigned long zend_ulong;
typedef unsigned short zend_ushort;

typedef struct _zend_module_entry zend_module_entry;
typedef struct _zval_struct zval;
typedef struct _zend_class_entry zend_class_entry;
typedef unsigned int zend_object_handle;
typedef struct _zend_object_handlers zend_object_handlers;

struct _hashtable;

typedef struct bucket {
	ulong h;						/* Used for numeric indexing */
	uint nKeyLength;
	void *pData;
	void *pDataPtr;
	struct bucket *pListNext;
	struct bucket *pListLast;
	struct bucket *pNext;
	struct bucket *pLast;
	char arKey[1]; /* Must be last element */
} Bucket;

typedef struct _hashtable {
	uint nTableSize;
	uint nTableMask;
	uint nNumOfElements;
	ulong nNextFreeElement;
	Bucket *pInternalPointer;	/* Used for element traversal */
	Bucket *pListHead;
	Bucket *pListTail;
	Bucket **arBuckets;
	void* pDestructor;
	zend_bool persistent;
	unsigned char nApplyCount;
	zend_bool bApplyProtection;
#if ZEND_DEBUG
	int inconsistent;
#endif
} HashTable;

typedef struct _zend_function_entry {
	const char *fname;
	void (*handler)(int ht, zval *return_value, zval **return_value_ptr, zval *this_ptr, int return_value_used, void ***tsrm_ls);
	const struct _zend_arg_info *arg_info;
	zend_uint num_args;
	zend_uint flags;
} zend_function_entry;

struct _zend_module_entry {
	unsigned short size;
	unsigned int zend_api;
	unsigned char zend_debug;
	unsigned char zts;
	const struct _zend_ini_entry *ini_entry;
	const struct _zend_module_dep *deps;
	const char *name;
	const struct _zend_function_entry *functions;
	int (*module_startup_func  )(int type, int module_number, void ***tsrm_ls);
	int (*module_shutdown_func )(int type, int module_number, void ***tsrm_ls);
	int (*request_startup_func )(int type, int module_number, void ***tsrm_ls);
	int (*request_shutdown_func)(int type, int module_number, void ***tsrm_ls);
	void (*info_func)(zend_module_entry *zend_module, void ***tsrm_ls);
	const char *version;
	size_t globals_size;
#ifdef ZTS
	ts_rsrc_id* globals_id_ptr;
#else
	void* globals_ptr;
#endif
	void (*globals_ctor)(void *global, void ***tsrm_ls);
	void (*globals_dtor)(void *global, void ***tsrm_ls);
	int (*post_deactivate_func)(void);
	int module_started;
	unsigned char type;
	void *handle;
	int module_number;
	char *build_id;
};

typedef struct _zend_guard {
	zend_bool in_get;
	zend_bool in_set;
	zend_bool in_unset;
	zend_bool in_isset;
	zend_bool dummy; /* sizeof(zend_guard) must not be equal to sizeof(void*) */
} zend_guard;

typedef struct _zend_object {
	zend_class_entry *ce;
	HashTable *properties;
	HashTable *guards; /* protects from __get/__set ... recursion */
} zend_object;

struct _zend_object_handlers {
	/* general object functions */
	void* add_ref;
	void* del_ref;
	void* clone_obj;
	/* individual object functions */
	void* read_property;
	void* write_property;
	void* read_dimension;
	void* write_dimension;
	void* get_property_ptr_ptr;
	void* get;
	void* set;
	void* has_property;
	void* unset_property;
	void* has_dimension;
	void* unset_dimension;
	void* get_properties;
	void* get_method;
	void* call_method;
	void* get_constructor;
	void* get_class_entry;
	void* get_class_name;
	void* compare_objects;
	void* cast_object;
	void* count_elements;
	void* get_debug_info;
	void* get_closure;
};

typedef struct _zend_object_value {
	zend_object_handle handle;
	zend_object_handlers *handlers;
} zend_object_value;

typedef union _zvalue_value {
	long lval;					/* long value */
	double dval;				/* double value */
	struct {
		char *val;
		int len;
	} str;
	HashTable *ht;				/* hash table value */
	zend_object_value obj;
} zvalue_value;

struct _zval_struct {
	/* Variable information */
	zvalue_value value;		/* value */
	zend_uint refcount__gc;
	zend_uchar type;	/* active type */
	zend_uchar is_ref__gc;
};

extern void php_info_print_table_start();
extern void php_info_print_table_header(int, ...);
extern void php_info_print_table_row(int, ...);
extern void php_info_print_table_row_ex(int, const char *, ...);
extern void php_info_print_table_end();

extern void zend_register_long_constant(const char *name, unsigned int name_len, long lval, int flags, int module_number, void ***tsrm_ls);
extern int  zend_register_functions(zend_class_entry *scope, const zend_function_entry *functions, HashTable *function_table, int type, void ***tsrm_ls);
extern void zend_unregister_functions(const zend_function_entry *functions, int count, HashTable *function_table, void ***tsrm_ls);
extern int _zend_get_parameters_array_ex(int param_count, zval ***argument_array, void ***tsrm_ls);
extern void convert_to_long(zval *op);
extern void _convert_to_string(zval *op, char*, int);
extern int zend_eval_string(char *str, zval *retval_ptr, char *string_name, void ***tsrm_ls);
extern int zend_parse_parameters(int num_args, void ***tsrm_ls, char *type_spec, ...);
extern void zend_register_string_constant(const char *name, uint name_len, char *strval, int flags, int module_number, void ***tsrm_ls);
extern void zend_error(int type, const char *format, ...);
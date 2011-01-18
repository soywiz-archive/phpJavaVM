/*
cl /O2 /MD dynacall.c
tcc -run dynacall.c
*/

#include <stdio.h>
#include <stdint.h>
#include <windows.h>
#include "miniphp.h"

#ifdef x64
	#error Not implemented yet
#endif

char dll_path[1024] = {0};

HMODULE GetCurrentModuleHandle() {
	HMODULE hMod = NULL;
	HANDLE lib = LoadLibraryA("kernel32.dll");
	void *GetModuleHandleExW = GetProcAddress(lib, "GetModuleHandleExW");
	((BOOL(* WINAPI)(DWORD, LPCTSTR, HMODULE *))GetModuleHandleExW)((DWORD)6, (LPCWSTR)(void *)(&GetCurrentModuleHandle), &hMod);
	return hMod;
}

char* dup_str(char* s) {
	char* ret = (char *)malloc(strlen(s) + 1);
	strcpy(ret, s);
	return ret;
}

extern volatile unsigned char _binary_dynacall_init_php_start[];

/*
int WINAPI MessageBox(
  __in_opt  HWND hWnd,
  __in_opt  LPCTSTR lpText,
  __in_opt  LPCTSTR lpCaption,
  __in      UINT uType
);
*/

// function, function_pointer
#define __CallBuffer_push_value_increment(type, value) { *(type *)(function_pointer) = (type)(value); function_pointer += sizeof(type); }

// MOV EAX, value
// PUSH EAX
#define CallBuffer_push_32(value) { \
	__CallBuffer_push_value_increment(uint8_t, 0xB8); \
	__CallBuffer_push_value_increment(uint32_t, (value)); \
	__CallBuffer_push_value_increment(uint8_t, 0x50); \
}

// ADD ESP, value
#define CallBuffer_add_esp(value) { \
	__CallBuffer_push_value_increment(uint8_t, 0x81); \
	__CallBuffer_push_value_increment(uint8_t, 0xC4); \
	__CallBuffer_push_value_increment(uint32_t, (value)); \
}


// MOV EAX, value
// CALL EAX
#define CallBuffer_call(func) { \
	\
	__CallBuffer_push_value_increment(uint8_t, 0xB8); \
	__CallBuffer_push_value_increment(uint32_t, (func)); \
	\
	__CallBuffer_push_value_increment(uint8_t, 0xFF); \
	__CallBuffer_push_value_increment(uint8_t, 0xD0); \
}

#define CallBuffer_ret() __CallBuffer_push_value_increment(uint8_t, 0xC3);

#define CallBuffer_execute() ((void (*)(void))function)();
#define CallBuffer_reset() function_pointer = function;

#define CallBuffer_call_ret_execute(func) { \
	CallBuffer_call(func); \
	CallBuffer_ret(); \
	CallBuffer_execute(); \
}

void main_test() {
	HANDLE lib = LoadLibraryA("User32.dll");
	void* func = GetProcAddress(lib, "MessageBoxA");

	char *function = (char *)VirtualAlloc(NULL, 1024, MEM_COMMIT | MEM_RESERVE, PAGE_EXECUTE_READWRITE);
	//char *function = (char*)malloc(1024);
	char *function_pointer;

	printf("%08X\n", function);
	
	if (function == NULL) {
		fprintf(stderr, "Memory not reserved.\n");
		return -1;
	}
	
	// MessageBoxA(0, "Hello", "World", 0);
	CallBuffer_reset();
	{
		CallBuffer_push_32(0);
		CallBuffer_push_32("Hello");
		CallBuffer_push_32("World");
		CallBuffer_push_32(0);
	}
	CallBuffer_call(func);
	CallBuffer_add_esp(4 * 4);
	CallBuffer_ret();
	CallBuffer_execute();
	CallBuffer_call_ret_execute(func);
	
	printf("%08X, %08X\n", lib, func);
	return 0;
}

const zend_function_entry module_functions[] = {
	{NULL, NULL, NULL, 0, 0},
	{NULL, NULL, NULL, 0, 0}
};

#define convert_to_string(zval) _convert_to_string((zval), __FILE__, __LINE__)

#define FUNCTION_CALL_MAGIC 0xF755557F

#define CALL_TYPE_C        0
#define CALL_TYPE_WINDOWS  1

typedef struct {
	unsigned int magic;
	char* format;
	int call_type;
	void* func;
} FUNCTION_CALL;

__stdcall void func_caller_end_call(FUNCTION_CALL* fc, int ht, zval *return_value, zval **return_value_ptr, zval *this_ptr, int return_value_used, void ***tsrm_ls) {
//void func_caller_end() {
	zval** args[64] = {0};
	int result;
	int num_params = strlen(fc->format);
	int n;
	int esp_add = 0;
	
	return_value->type = IS_NULL;
	
	result = _zend_get_parameters_array_ex(num_params, args, tsrm_ls);
	if (result == -1) {
		zend_error(E_WARNING, "Invalid parameters\n");
		return;
	}
	
	if (!fc || !fc->func) {
		zend_error(E_WARNING, "Invalid call\n");
		return;
	}

	char *function = (char *)VirtualAlloc(NULL, 1024, MEM_COMMIT | MEM_RESERVE, PAGE_EXECUTE_READWRITE);
	char *function_pointer;

	CallBuffer_reset();
	{
		for (n = 0; n < num_params; n++) {
			switch (fc->format[n]) {
				case 'i':
					convert_to_long(*args[n]);
					CallBuffer_push_32((*args[n])->value.lval);
					esp_add += 4;
				break;
				case 's':
					convert_to_string(*args[n]);
					CallBuffer_push_32((*args[n])->value.str.val);
					esp_add += 4;
				break;
				default:
					zend_error(E_WARNING, "Unknown param format '%c'", n);
				break;
			}
		}
	}
	CallBuffer_call(fc->func);
	if (fc->call_type == CALL_TYPE_C) {
		CallBuffer_add_esp(esp_add);
	}
	CallBuffer_ret();
	CallBuffer_execute();
}

char* get_EIP(void) {
	__asm("movl +0x04(%ebp), %eax;");
}


typedef int(* PRINTF)(const char*, ...);

void func_caller(int ht, zval *return_value, zval **return_value_ptr, zval *this_ptr, int return_value_used, void ***tsrm_ls) {
	char*(* _get_EIP)(void) = get_EIP;
	int(* _printf)(const char*, ...) = printf;
	char *fc_base = _get_EIP();
	FUNCTION_CALL *fc;
	while (1) {
		fc_base--;
		fc = (FUNCTION_CALL *)fc_base;
		if (fc->magic == FUNCTION_CALL_MAGIC) {
			break;
		}
	}
	void (* __stdcall _func_caller_end_call)(FUNCTION_CALL* fc, int ht, zval *return_value, zval **return_value_ptr, zval *this_ptr, int return_value_used, void ***tsrm_ls) = func_caller_end_call;

	_func_caller_end_call(fc, ht, return_value, return_value_ptr, this_ptr, return_value_used, tsrm_ls);
}

void func_caller_end() {
}

void* CREATE_HANDLER(char* format, void* func, int call_type) {
	int func_size = (char *)func_caller_end - (char *)func_caller;
	//printf("SIZE: %d\n", func_size);
	char *data = (char *)VirtualAlloc(NULL, sizeof(FUNCTION_CALL) + func_size, MEM_COMMIT | MEM_RESERVE, PAGE_EXECUTE_READWRITE);
	((FUNCTION_CALL *)data)->magic     = FUNCTION_CALL_MAGIC;
	((FUNCTION_CALL *)data)->format    = format;
	((FUNCTION_CALL *)data)->func      = func;
	((FUNCTION_CALL *)data)->call_type = call_type;
	memcpy(data + sizeof(FUNCTION_CALL), func_caller, func_size);
	return data + sizeof(FUNCTION_CALL);
}

void *GetLibraryProcAddress(char* library, char* symbol) {
	HANDLE lib = LoadLibraryA(library);
	if (lib == NULL) {
		zend_error(E_WARNING, "Can't load library '%s'", library);
		return NULL;
	}
	void* func = GetProcAddress(lib, symbol);
	if (func == NULL) {
		zend_error(E_WARNING, "Can't find symbol '%s' in library '%s'", symbol, library);
		return NULL;
	}
	return func;
}

void dummy_func_handler(int ht, zval *return_value, zval **return_value_ptr, zval *this_ptr, int return_value_used, void ***tsrm_ls) {
}

typedef struct _UNREGISTER_FUNCTION_LIST {
	zend_function_entry *functions;
	struct _UNREGISTER_FUNCTION_LIST *next;
} UNREGISTER_FUNCTION_LIST;

UNREGISTER_FUNCTION_LIST unregister_first = {0};
UNREGISTER_FUNCTION_LIST *unregister_last = &unregister_first;

void add_unregister(zend_function_entry *functions) {
	unregister_last->next = malloc(UNREGISTER_FUNCTION_LIST);
	unregister_last->next->functions = functions;
	unregister_last->next->next = NULL;
	unregister_last = unregister_last->next;
}

void unregister_list(void ***tsrm_ls) {
	UNREGISTER_FUNCTION_LIST *current = &unregister_first;
	while (current = current->next) {
		if (current->functions) {
			printf("Unregistering...%s\n", current->functions[0].fname);
			zend_unregister_functions(current->functions, 1, NULL, tsrm_ls);
		}
	}
}

void RegisterFunctionHandler(char *name, void* handler, void ***tsrm_ls) {
	zend_function_entry *local_module_functions = (zend_function_entry *)malloc(sizeof(zend_function_entry) * 2); memset(local_module_functions, 0, sizeof(zend_function_entry) * 2);

	local_module_functions[0].fname = name;
	local_module_functions[0].handler = handler;
	local_module_functions[0].arg_info = NULL;
	local_module_functions[0].num_args = 0;
	local_module_functions[0].flags = 0;

	add_unregister(local_module_functions);
	zend_register_functions(NULL, local_module_functions, NULL, 1/* MODULE_PERSISTENT */, tsrm_ls);
}

void RegisterFunction(char *name, char *format, void* func, int calltype, void ***tsrm_ls) {
	RegisterFunctionHandler(name, CREATE_HANDLER(format, func, calltype), tsrm_ls);
}

int module_startup_func(int type, int module_number, void ***tsrm_ls) {
	printf("module_startup_func\n");

	return SUCCESS;
}

int module_shutdown_func(int type, int module_number, void ***tsrm_ls) {
	printf("module_shutdown_func\n");
	return SUCCESS;
}

void PHP_RegisterFunction(int ht, zval *return_value, zval **return_value_ptr, zval *this_ptr, int return_value_used, void ***tsrm_ls) {
	char *fname; int fname_len;
	char *format; int format_len;
	char *dll; int dll_len;
	char *dll_symbol; int dll_symbol_len;
	int calltype = CALL_TYPE_C;
	
	return_value->type = IS_NULL;
	
	// http://www.hospedajeydominios.com/mambo/documentacion-manual_php-pagina-zend_arguments_retrieval.html
	if (zend_parse_parameters(ht, tsrm_ls, "ssss|l", &fname, &fname_len, &format, &format_len, &dll, &dll_len, &dll_symbol, &dll_symbol_len, &calltype) == FAILURE) {
		zend_error(E_WARNING, "Bad call for RegisterFunction");
		return;
	}

	RegisterFunction(dup_str(fname), dup_str(format), GetLibraryProcAddress(dll, dll_symbol), calltype, tsrm_ls);
}

int request_startup_func(int type, int module_number, void ***tsrm_ls) {
	printf("request_startup_func\n");
	
	static int once = 1;

	if (once) { once = 0;
		func_caller_end(); // to avoid symbol deletion

		GetModuleFileName(GetCurrentModuleHandle(), dll_path, sizeof(dll_path));
		strrchr(dll_path, '\\')[0] = 0;
		//printf("'%s'\n", dll_path);

		//printf("%08X\n", module_startup_func);

		zend_register_long_constant("CALL_TYPE_WINDOWS", 18, CALL_TYPE_WINDOWS, 0, 0, tsrm_ls);
		zend_register_long_constant("CALL_TYPE_C", 12, CALL_TYPE_C, 0, 0, tsrm_ls);
		zend_register_string_constant("DYNACALL_PATH", 14, (dll_path), 0, 0, tsrm_ls);

		RegisterFunctionHandler("RegisterFunction", PHP_RegisterFunction, tsrm_ls);
		
		zval* retval;
		zend_eval_string(((char *)_binary_dynacall_init_php_start) + 5, retval, "(eval)", tsrm_ls);
	}

	return SUCCESS;
}

int request_shutdown_func(int type, int module_number, void ***tsrm_ls) {
	//unregister_list(tsrm_ls);
	printf("request_shutdown_func\n");
	return SUCCESS;
}

void info_func(zend_module_entry *zend_module, void ***tsrm_ls) {
	printf("info_func\n");
}

zend_module_entry module_module_entry = {
	sizeof(zend_module_entry), 20090626, ZEND_DEBUG, USING_ZTS, NULL, NULL,
	"dynacall",
	module_functions,
	module_startup_func,
	module_shutdown_func,
	request_startup_func,
	request_shutdown_func,
	info_func,
	NULL,
	0, NULL, NULL, NULL, NULL, 0, 0, NULL, 0, "API20090626,TS,VC9"
};

__declspec(dllexport) zend_module_entry* get_module() {
	return &module_module_entry;
}

__declspec(dllexport) BOOL WINAPI DllMain(HINSTANCE hInstance, DWORD fdwReason, LPVOID lpvReserved) {
	switch (fdwReason) {
		case DLL_PROCESS_ATTACH:
		break;
		case DLL_THREAD_ATTACH:
		break;
		case DLL_THREAD_DETACH:
			//ts_free_thread();
		break;
		case DLL_PROCESS_DETACH:
			//if (isapi_sapi_module.shutdown) isapi_sapi_module.shutdown(&sapi_module);
			//sapi_shutdown();
			//tsrm_shutdown();
		break;
	}
	return TRUE;
}

/*int main(char** argv, int argc) {
	main_test();
}*/

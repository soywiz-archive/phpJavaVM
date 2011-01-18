<?php

class MimeDocument {
	public $filename;
	public $headers;
	public $content;
	public $childs;

	public function isAttachment() {
		return strlen($this->filename);
	}
	
	public function find($header, $pattern, &$matches = null, $reset = true) {
		if ($reset) $matches = array();
		if (isset($this->headers[$header]) && preg_match($pattern, $this->headers[$header])) {
			$matches[] = $this;
		}
		foreach ($this->childs as $child) {
			$child->find($header, $pattern, $matches, false);
		}
		return $matches;
	}

	public function getBodyText() {
		$list = $this->childs[0]->find('content-type', '@text/plain@');
		return count($list) ? $list[0]->getContentUtf8() : '';
	}

	public function getBodyHtml() {
		$list = $this->childs[0]->find('content-type', '@text/html@');
		return count($list) ? $list[0]->getContentUtf8() : $this->getBodyText();
	}
	
	public function getContent() {
		if ($this->content !== null) return $this->content;
		return count($this->childs) ? $this->childs[0]->getContent() : null;
	}

	public function getContentUtf8() {
		$content = $this->getContent();
		if (isset($this->headers['content-type']) && preg_match('@charset=(\\S*)@', $this->headers['content-type'], $matches)) {
			$content = mb_convert_encoding($content, 'utf-8', $matches[1]);
		}
		return $content;

	}

	public function getSubject() {
		$text = $this->headers['subject'];
		// Very faked:
		if (strpos($text, '=?ISO-8859-1?Q?') !== false) {
			$text = str_replace('=?ISO-8859-1?Q?', '', $text);
			$text = preg_replace('@\\?=\\s?@', '', $text);
			$text = mb_convert_encoding($text, 'utf-8', 'iso-8859-1');
		}
		$text = quoted_printable_decode($text);
		return str_replace('_', ' ', $text);
	}

	public function getFrom() {
		return $this->headers['from'];
	}

	public function getTo() {
		return $this->headers['to'];
	}
	
	public function getDate() {
		return strtotime($this->headers['date']);
	}
	
	public function getContentType() {
		if (isset($this->headers['content-type'])) {
			$list = explode(';', $this->headers['content-type'], 2);
			return strtolower(trim($list[0]));
		}
		return mime_content_type($this->filename);
	}

	public function getAttachments() {
		$attachments = array();
		if ($this->isAttachment()) $attachments[] = $this;
		foreach ($this->childs as $child) if ($child->isAttachment()) $attachments[] = $child;
		return $attachments;
	}
}

class Mime {
	protected $data;
	protected $cursor;
	public $document;

	public function __construct($data) {
		$this->data = $data;
		$this->cursor = 0;
		$this->document = new MimeDocument;
		$headers = array();
		$document = &$this->document;
		$last = '';
		$content = '';
		while (!$this->eof()) {
			$line = $this->readline();
			if (preg_match('@^([\\w-]+):(.*)$@', $line, $matches)) {
				$key   = strtolower($matches[1]);
				if (!isset($headers[$key])) {
					$last = &$headers[$key];
				} else {
					if (!is_array($headers[$key])) $headers[$key] = array($headers[$key]);
					$last = &$headers[$key][];
				}
				$last = trim($matches[2]);
			} else {
				if (strlen($line)) {
					$last .= ' ' . trim($line);
				} else {
					$content = $this->readleft();
				}
			}
		}
		
		$document = new MimeDocument;
		$document->headers = $headers;
		$document->filename = '';
		$document->content = null;
		$document->childs = array();

		if (isset($headers['content-type'])) {
			if (preg_match('@^multipart/(mixed|alternative); boundary=(?P<boundary>.*)$@', $headers['content-type'], $matches)) {
				$boundary = "--{$matches['boundary']}";
				foreach (array_slice(explode($boundary, $content), 1) as $part) {
					if (substr($part, 0, 2) == '--') break;
					$mime = new Mime(ltrim($part));
					$document->childs[] = $mime->document;
				}
				return;
			}
		}

		if (isset($headers['content-type'])) {
			if (preg_match('@name=\"(.*)\"@', $headers['content-type'], $matches)) {
				$document->filename = basename($matches[1]);
			}
		}

		if (isset($headers['content-disposition'])) {
			if (preg_match('@attachment; filename=\"(.*)\"@', $headers['content-disposition'], $matches)) {
				$document->filename = basename($matches[1]);
			}
		}

		$document->content = $content;
		if (isset($headers['content-transfer-encoding'])) {
			switch ($headers['content-transfer-encoding']) {
				case 'base64':
					$document->content = base64_decode($content);
				break;
				case 'quoted-printable':
					$document->content = quoted_printable_decode($content);
				break;
			}
		}
	}
	
	public function eof() {
		return ($this->cursor === false);
	}
	
	public function readleft() {
		if ($this->eof()) return false;
		$data = substr($this->data, $this->cursor);
		$this->cursor = false;
		return $data;
	}

	public function readline() {
		if ($this->eof()) return false;
		$ppos = $this->cursor;
		$pos = strpos($this->data, "\r\n", $this->cursor);
		if ($pos === false) {
			$this->cursor = false;
			return false;
		}
		$this->cursor = $pos + 2;
		return substr($this->data, $ppos, $pos - $ppos);
	}

	static public function parse($data) {
		$mime = new Mime($data);
		return $mime->document;
	}
}

/*
$document = Mime::parse(file_get_contents('emails/4.txt'));
print_r($document->getSubject());
print_r($document->getFrom());
print_r($document->getTo());
print_r($document->getContent());
print_r($document->getBodyText());
print_r($document->getBodyHtml());
print_r($document->getAttachments());
*/

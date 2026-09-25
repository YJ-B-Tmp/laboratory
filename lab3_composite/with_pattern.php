<?php
// Lab 3 - WITH Composite (GOOD starter, live API, has TODOs)
interface ForumComponent {
    public function display(int $depth = 0): void;
}
class Post implements ForumComponent {
    public function __construct(private string $author, private string $message) {}
    public function display(int $depth = 0): void {
        $indent = str_repeat("  ", $depth);
        echo $indent . "- Post by {$this->author}: {$this->message}\n";
    }
}
class Thread implements ForumComponent {
    /** @var ForumComponent[] */
    private array $children = [];
    public function __construct(private string $title) {}
    public function add(ForumComponent $c): void { $this->children[] = $c; }
    public function display(int $depth = 0): void {
        // TODO: loop over $children and display with $depth+1
        echo str_repeat("  ", $depth) . "+ Thread: {$this->title}\n";
        foreach ($this->children as $child) { $child->display($depth + 1); }
    }
    public static function fromApi(int $postId): self {
        $postJson = file_get_contents("https://jsonplaceholder.typicode.com/posts/$postId");
        $post = json_decode($postJson, true);
        $thread = new self($post["title"]);
        $thread->add(new Post("Author {$post['userId']}", substr($post["body"],0,40)."..."));
        $commentsJson = file_get_contents("https://jsonplaceholder.typicode.com/posts/$postId/comments");
        $comments = json_decode($commentsJson, true);
        $replies = new Thread("Replies");
        foreach (array_slice($comments,0,2) as $c) $replies->add(new Post($c["email"], substr($c["body"],0,30)."..."));
        $thread->add($replies);
        return $thread;
    }
}

interface TextComponent{
    public function getText(): string;
    public function embed(): array;
    public function display(int $depth = 0): void;
}

//DOCU
class Document implements TextComponent {
    private array $children = [];
    public function __construct(private string $title) {}
    public function add(TextComponent $c): void { $this->children[] = $c; }
    public function getText(): string {
        return implode("\n", array_map(fn($c) => $c->getText(), $this->children));
    }
    public function embed(): array {
        return array_merge(...array_map(fn($c) => $c->embed(), $this->children));
    }
    public function display(int $depth = 0): void {
        echo str_repeat("  ", $depth) . "+ Document: {$this->title}\n";
        foreach ($this->children as $child) { $child->display($depth + 1); }
    }
}

//SECTION
class Section implements TextComponent {
    private array $children = [];
    public function __construct(private string $heading) {}
    public function add(TextComponent $c): void { $this->children[] = $c; }
    public function getText(): string {
        return implode("\n", array_map(fn($c) => $c->getText(), $this->children));
    }
    public function embed(): array {
        return array_merge(...array_map(fn($c) => $c->embed(), $this->children));
    }
    public function display(int $depth = 0): void {
        echo str_repeat("  ", $depth) . "+ Section: {$this->heading}\n";
        foreach ($this->children as $child) { $child->display($depth + 1); }
    }
}

//CHUNK
class Chunk implements TextComponent {
    public function __construct(private string $text) {}
    public function getText(): string { return $this->text; }
    public function embed(): array { return [strlen($this->text), substr_count($this->text, ' ') + 1]; }
    public function display(int $depth = 0): void {
        echo str_repeat("  ", $depth) . "- Chunk: " . substr($this->text, 0, 30) . "...\n";
    }
}

// TODO: Create RAG variant: Document/Section/Chunk with getText() and embed()
if (basename(__FILE__)===basename($_SERVER['SCRIPT_FILENAME'])) {
    echo "WITH Composite (GOOD - complete TODOs):\n";
    $thread = Thread::fromApi(1); // live to jsonplaceholder.typicode.com
    $thread->display();
    echo "\n";
    $doc = new Document("Intro to Design Patterns");
    $section = new Section("Structural Patterns");
    $section->add(new Chunk("The Adapter pattern converts one interface into another expected by the client."));
    $section->add(new Chunk("The Composite pattern lets you treat individual objects and groups uniformly."));
    $doc->add($section);
    $doc->display();
    echo "Full text length: " . strlen($doc->getText()) . " chars\n";
    
}

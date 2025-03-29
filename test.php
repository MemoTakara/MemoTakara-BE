public function flashcards()
{
return $this->hasMany(Flashcard::class, 'collection_id');
}

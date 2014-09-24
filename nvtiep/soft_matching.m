function pair = soft_matching(word_ids_a, word_ids_b)

a = reshape(word_ids_a, 1, size(word_ids_a,1)*size(word_ids_a,2));
b = reshape(word_ids_b, 1, size(word_ids_b,1)*size(word_ids_b,2));
[~, ia, ib] = intersect(a, b);
ia = floor((ia+2)/3);
ib = floor((ib+2)/3);

pair = unique([ia;ib]', 'rows')';

end

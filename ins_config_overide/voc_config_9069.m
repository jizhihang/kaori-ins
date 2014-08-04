function conf = voc_config_9069()
	conf.pascal.year = '9069';
	conf.paths.model_dir = '/net/per610a/export/das11f/ledduy/trecvid-ins-2013/model/9069/';
	conf.training.log = @(x) sprintf([conf.paths.model_dir '%s.log'], x);
	conf.pascal.VOCopts.annopath = '9069/Annotations/%s.txt';
	conf.pascal.VOCopts.imgsetpath = '9069/ImageSets/%s.txt';
	conf.pascal.VOCopts.imgpath = '9069/Images/%s.txt';
end
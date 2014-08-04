function conf = voc_config_9092()
	conf.pascal.year = '9092';
	conf.paths.model_dir = '/net/per610a/export/das11f/ledduy/trecvid-ins-2013/model/9092/';
	conf.training.log = @(x) sprintf([conf.paths.model_dir '%s.log'], x);
	conf.pascal.VOCopts.annopath = '9092/Annotations/%s.txt';
	conf.pascal.VOCopts.imgsetpath = '9092/ImageSets/%s.txt';
	conf.pascal.VOCopts.imgpath = '9092/Images/%s.txt';
end
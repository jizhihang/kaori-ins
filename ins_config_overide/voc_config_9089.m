function conf = voc_config_9089()
	conf.pascal.year = '9089';
	conf.paths.model_dir = '/net/per610a/export/das11f/ledduy/trecvid-ins-2013/model/9089/';
	conf.training.log = @(x) sprintf([conf.paths.model_dir '%s.log'], x);
	conf.pascal.VOCopts.annopath = '9089/Annotations/%s.txt';
	conf.pascal.VOCopts.imgsetpath = '9089/ImageSets/%s.txt';
	conf.pascal.VOCopts.imgpath = '9089/Images/%s.txt';
end
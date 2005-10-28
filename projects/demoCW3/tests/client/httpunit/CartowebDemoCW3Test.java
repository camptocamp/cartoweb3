import junit.framework.Test;
import junit.framework.TestCase;
import junit.framework.TestSuite;

public class CartowebDemoCW3Test extends AbstractCartowebTest {
    
    public CartowebDemoCW3Test( String name )
    {
      super( name );
    }
    
    public static void main( String args[] ) {
        AbstractCartowebTest.main(args);
        junit.textui.TestRunner.run( suite() );
    }
    
    public static Test suite()
    {
      return new TestSuite( CartowebDemoCW3Test.class );
    }
}
